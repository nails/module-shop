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

use Nails\Factory;
use Nails\Admin\Helper;
use Nails\Shop\Controller\BaseAdmin;

class Vouchers extends BaseAdmin
{
    protected $oVoucherModel;
    protected $aVoucherTypes;

    // --------------------------------------------------------------------------

    /**
     * Announces this controller's navGroups
     * @return stdClass
     */
    public static function announce()
    {
        if (userHasPermission('admin:shop:vouchers:manage')) {

            $oNavGroup = Factory::factory('Nav', 'nailsapp/module-admin');
            $oNavGroup->setLabel('Shop');
            $oNavGroup->setIcon('fa-shopping-cart');
            $oNavGroup->addAction('Manage Vouchers');
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

        $permissions['manage']     = 'Manage Vouchers';
        $permissions['create']     = 'Create Vouchers';
        $permissions['activate']   = 'Activate Vouchers';
        $permissions['deactivate'] = 'Deactivate Vouchers';

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
        $this->oVoucherModel = Factory::model('Voucher', 'nailsapp/module-shop');
        $this->aVoucherTypes = $this->oVoucherModel->getTypes();

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
        if (!userHasPermission('admin:shop:vouchers:manage')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Set method info
        $this->data['page']->title  = 'Manage Vouchers';
        $this->data['voucherTypes'] = $this->aVoucherTypes;

        // --------------------------------------------------------------------------

        $tablePrefix = $this->oVoucherModel->getTablePrefix();

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
        $aTypeFilter = array();
        foreach ($this->data['voucherTypes'] as $sValue => $sLabel) {
            $aTypeFilter[] = array($sLabel, $sValue);
        }
        $filters   = array();
        $filters[] = Helper::searchFilterObject(
            $tablePrefix . '.type',
            'View only',
            $aTypeFilter
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
        $totalRows              = $this->oVoucherModel->countAll($data);
        $this->data['vouchers'] = $this->oVoucherModel->getAll($page, $perPage, $data);

        //  Set Search and Pagination objects for the view
        $this->data['search']     = Helper::searchObject(true, $sortColumns, $sortOn, $sortOrder, $perPage, $keywords, $filters);
        $this->data['pagination'] = Helper::paginationObject($page, $perPage, $totalRows);

        // --------------------------------------------------------------------------

        if (userHasPermission('admin:shop:vouchers:create')) {

            Helper::addHeaderButton('admin/shop/vouchers/create', 'Create Voucher');
        }

        // --------------------------------------------------------------------------

        //  Load assets
        $this->asset->library('ZEROCLIPBOARD');
        $this->asset->load('nails.admin.shop.vouchers.min.js', 'NAILS');
        $this->asset->inline('voucher = new NAILS_Admin_Shop_Vouchers();', 'JS');

        // --------------------------------------------------------------------------

        Helper::loadView('index');
    }

    // --------------------------------------------------------------------------

    /**
     * Create a new voucher
     * @return void
     */
    public function create()
    {
        if (!userHasPermission('admin:shop:vouchers:create')) {

            $this->session->set_flashdata('error', 'You do not have permission to create vouchers.');
            redirect('admin/shop/vouchers');
        }

        // --------------------------------------------------------------------------

        $this->load->model('shop/shop_product_model');
        $this->load->model('shop/shop_product_type_model');

        // --------------------------------------------------------------------------

        if ($this->input->post()) {

            $oFormValidation = Factory::service('FormValidation');

            //  Common
            $oFormValidation->set_rules('type', '', 'required|callback_callbackVoucherValidType');
            $oFormValidation->set_rules('code', '', 'required|is_unique[' . NAILS_DB_PREFIX . 'shop_voucher.code]|callback_callbackVoucherValidCode');
            $oFormValidation->set_rules('label', '', 'required');
            $oFormValidation->set_rules('valid_from', '', 'required|callback_callbackVoucherValidFrom');
            $oFormValidation->set_rules('valid_to', '', 'callback_callbackVoucherValidTo');

            //  Voucher Type specific rules
            switch ($this->input->post('type')) {

                case 'LIMITED_USE':

                    $oFormValidation->set_rules('limited_use_limit', '', 'required|is_natural_no_zero');
                    $oFormValidation->set_rules('discount_type', '', 'required|callback_callbackVoucherValidDiscountType');
                    $oFormValidation->set_rules('discount_application', '', 'required|callback_callbackVoucherValidDiscountApplication');

                    $oFormValidation->set_message('is_natural_no_zero', 'Only positive integers are valid.');
                    break;

                case 'NORMAL':
                default:

                    $oFormValidation->set_rules('discount_type', '', 'required|callback_callbackVoucherValidDiscountType');
                    $oFormValidation->set_rules('discount_application', '', 'required|callback_callbackVoucherValidDiscountApplication');
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

                    $oFormValidation->set_rules('discount_value', '', 'required|is_natural_no_zero|greater_than[0]|less_than[101]');
                    $oFormValidation->set_message('is_natural_no_zero', 'Only positive integers are valid.');
                    $oFormValidation->set_message('greater_than', 'Must be in the range 1-100');
                    $oFormValidation->set_message('less_than', 'Must be in the range 1-100');
                    break;

                case 'AMOUNT':

                    $oFormValidation->set_rules('discount_value', '', 'required|numeric|greater_than[0]');
                    $oFormValidation->set_message('greater_than', 'Must be greater than 0');
                    break;

                default:

                    //  No specific rules
                    break;
            }

            //  Discount application specific rules
            switch ($this->input->post('discount_application')) {

                case 'PRODUCT_TYPES':

                    $oFormValidation->set_rules('product_type_id', '', 'required|callback_callbackVoucherValidProductType');
                    break;

                case 'PRODUCT':

                    $oFormValidation->set_rules('product_id', '', 'required|callback_callbackVoucherValidProduct');
                    break;

                case 'PRODUCTS':
                case 'SHIPPING':
                case 'ALL':
                default:

                    //  No specific rules
                    break;
            }

            $oFormValidation->set_message('required', lang('fv_required'));
            $oFormValidation->set_message('is_unique', 'Code already in use.');

            if ($oFormValidation->run($this)) {

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

                if ($this->input->post('discount_application') == 'PRODUCT') {

                    $data['product_id'] = $this->input->post('product_id');
                }

                if ($this->input->post('discount_application') == 'PRODUCT_TYPES') {

                    $data['product_type_id'] = $this->input->post('product_type_id');
                }

                // --------------------------------------------------------------------------

                //  Attempt to create
                if ($this->oVoucherModel->create($data)) {

                    $this->session->set_flashdata('success', 'Voucher "' . $data['code'] . '" was created successfully.');
                    redirect('admin/shop/vouchers');

                } else {

                    $this->data['error']  = 'There was a problem creating the voucher. ';
                    $this->Data['error'] .= $this->oVoucherModel->lastError();
                }

            } else {

                $this->data['error'] = lang('fv_there_were_errors');
            }
        }

        // --------------------------------------------------------------------------

        $this->data['page']->title  = 'Create Voucher';
        $this->data['voucherTypes'] = $this->aVoucherTypes;

        // --------------------------------------------------------------------------

        //  Fetch data
        $this->data['product_types'] = $this->shop_product_type_model->getAllFlat();

        // --------------------------------------------------------------------------

        //  Load assets
        $this->asset->load('nails.admin.shop.vouchers.createEdit.min.js', 'NAILS');
        $this->asset->inline('voucher = new NAILS_Admin_Shop_Vouchers_CreateEdit();', 'JS');

        // --------------------------------------------------------------------------

        //  Load views
        Helper::loadView('edit');
    }

    // --------------------------------------------------------------------------

    /**
     * Activate a voucher
     * @return void
     */
    public function activate()
    {
        if (!userHasPermission('admin:shop:vouchers:activate')) {

            $status  = 'error';
            $message = 'You do not have permission to activate vouchers.';

        } else {

            $id = $this->uri->segment(5);

            if ($this->oVoucherModel->activate($id)) {

                $status  = 'success';
                $message = 'Voucher was activated successfully.';

            } else {

                $status   = 'error';
                $message  = 'There was a problem activating the voucher. ';
                $message .= $this->oVoucherModel->lastError();
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
        if (!userHasPermission('admin:shop:vouchers:deactivate')) {

            $status  = 'error';
            $message = 'You do not have permission to suspend vouchers.';

        } else {

            $id = $this->uri->segment(5);

            if ($this->oVoucherModel->suspend($id)) {

                $status  = 'success';
                $message = 'Voucher was suspended successfully.';

            } else {

                $status   = 'error';
                $message  = 'There was a problem suspending the voucher. ';
                $message .= $this->oVoucherModel->lastError();
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
    public function callbackVoucherValidCode(&$str)
    {
        $str = strtoupper($str);

        if (preg_match('/[^a-zA-Z0-9]/', $str)) {

            $oFormValidation = Factory::service('FormValidation');
            $oFormValidation->set_message('callbackVoucherValidCode', 'Invalid characters.');
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
    public function callbackVoucherValidType($str)
    {
        $oFormValidation = Factory::service('FormValidation');
        $oFormValidation->set_message('callbackVoucherValidType', 'Invalid voucher type.');
        return !empty($this->aVoucherTypes[$str]);
    }

    // --------------------------------------------------------------------------

    /**
     * Form Validation: Validate a voucher's discount tye
     * @param  string $str The voucher discount type
     * @return boolean
     */
    public function callbackVoucherValidDiscountType($str)
    {
        $valid_types     = array('PERCENTAGE', 'AMOUNT');
        $oFormValidation = Factory::service('FormValidation');
        $oFormValidation->set_message('callbackVoucherValidDiscountType', 'Invalid discount type.');
        return array_search($str, $valid_types) !== false;
    }

    // --------------------------------------------------------------------------

    /**
     * Form Validation: Validate a voucher's discount application
     * @todo: complete this, I'm sure it used to be here
     * @param  string $str The voucher discount type
     * @return boolean
     */
    public function callbackVoucherValidDiscountApplication($str)
    {
        return true;
    }

    // --------------------------------------------------------------------------

    /**
     * Form Validation: Validate a voucher's product type
     * @param  string $str The voucher product type
     * @return boolean
     */
    public function callbackVoucherValidProductType($str)
    {
        $oFormValidation = Factory::service('FormValidation');
        $oFormValidation->set_message('callbackVoucherValidProductType', 'Invalid product type.');
        return (bool) $this->shop_product_type_model->getById($str);
    }

    // --------------------------------------------------------------------------

    /**
     * Form Validation: Validate a voucher's product ID
     * @param  string $str The voucher product ID
     * @return boolean
     */
    public function callbackVoucherValidProduct($str)
    {
        $oFormValidation = Factory::service('FormValidation');
        $oFormValidation->set_message('callbackVoucherValidProduct', 'Invalid product.');
        return (bool) $this->shop_product_model->getById($str);
    }

    // --------------------------------------------------------------------------

    /**
     * Form Validation: Validate a voucher's from date
     * @param  string $str The voucher from date
     * @return boolean
     */
    public function callbackVoucherValidFrom(&$str)
    {
        $oFormValidation = Factory::service('FormValidation');

        //  Check $str is a valid date
        $date = date('Y-m-d H:i:s', strtotime($str));

        //  Check format of str
        if (preg_match('/^\d\d\d\d\-\d\d-\d\d$/', trim($str))) {

            //in YYYY-MM-DD format, add the time
            $str = trim($str) . ' 00:00:00';
        }

        if ($date != $str) {

            $oFormValidation->set_message('callbackVoucherValidFrom', 'Invalid date.');
            return false;
        }

        //  If valid_to is defined make sure valid_from isn't before it
        if ($this->input->post('valid_to')) {

            $date = strtotime($this->input->post('valid_to'));

            if (strtotime($str) >= $date) {

                $oFormValidation->set_message('callbackVoucherValidFrom', 'Valid From date cannot be after Valid To date.');
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
    public function callbackVoucherValidTo(&$str)
    {
        //  If empty ignore
        if (!$str) {
            return true;
        }

        // --------------------------------------------------------------------------

        $oFormValidation = Factory::service('FormValidation');

        //  Check $str is a valid date
        $date = date('Y-m-d H:i:s', strtotime($str));

        //  Check format of str
        if (preg_match('/^\d\d\d\d\-\d\d\-\d\d$/', trim($str))) {

            //in YYYY-MM-DD format, add the time
            $str = trim($str) . ' 00:00:00';
        }

        if ($date != $str) {

            $oFormValidation->set_message('callbackVoucherValidTo', 'Invalid date.');
            return false;
        }

        //  Make sure valid_from isn't before it
        $date = strtotime($this->input->post('valid_from'));

        if (strtotime($str) <= $date) {

            $oFormValidation->set_message('callbackVoucherValidTo', 'Valid To date cannot be before Valid To date.');
            return false;
        }

        return true;
    }
}
