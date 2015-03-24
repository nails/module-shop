<?php

namespace App\Api\Shop;

/**
 * Admin API end points: Shop Vouchers
 *
 * @package     Nails
 * @subpackage  module-shop
 * @category    Controller
 * @author      Nails Dev Team
 * @link
 */

class Voucher extends \ApiController
{
    public static $requiresAuthentication = true;

    // --------------------------------------------------------------------------

    /**
     * Construct the controller
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('shop/shop_voucher_model');
    }

    // --------------------------------------------------------------------------

    /**
     * Generates a valid voucher code
     * @return array
     */
    public function getGenerateCode()
    {
        if (!userHasPermission('admin:shop:vouchers:create')) {

            return array(
                'status' => 401,
                'error'  => 'You do not have permission to create vouchers.'
            );

        } else {

            $out = array();

            $this->load->model('shop/shop_voucher_model');

            $out['code'] = $this->shop_voucher_model->generateValidCode();

            return $out;
        }
    }
}
