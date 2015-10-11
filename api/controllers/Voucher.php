<?php

namespace Nails\Api\Shop;

/**
 * Admin API end points: Shop Vouchers
 *
 * @package     Nails
 * @subpackage  module-shop
 * @category    Controller
 * @author      Nails Dev Team
 * @link
 */

class Voucher extends \Nails\Api\Controllers\Base
{
    public static $requiresAuthentication = true;
    protected $maintenance;

    // --------------------------------------------------------------------------

    /**
     * Construct the controller
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('shop/shop_voucher_model');

        $this->maintenance = new \stdClass();
        $this->maintenance->enabled = (bool) app_setting('maintenance_enabled', 'shop');
        if ($this->maintenance->enabled) {

            //  Allow shop admins access
            if (userHasPermission('admin:shop:*')) {
                $this->maintenance->enabled = false;
            }
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Sets the maintenance ehaders and returns the status/error message
     * @return array
     */
    protected function renderMaintenance()
    {
        $this->output->set_header($this->input->server('SERVER_PROTOCOL') . ' 503 Service Temporarily Unavailable');
        $this->output->set_header('Status: 503 Service Temporarily Unavailable');
        $this->output->set_header('Retry-After: 7200');

        return array(
            'status' => '503',
            'error'  => 'Down for maintenance'
        );
    }

    // --------------------------------------------------------------------------

    /**
     * Generates a valid voucher code
     * @return array
     */
    public function getGenerateCode()
    {
        if ($this->maintenance->enabled) {

            return $this->renderMaintenance();
        }

        // --------------------------------------------------------------------------

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
