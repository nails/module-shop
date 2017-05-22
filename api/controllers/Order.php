<?php

/**
 * Shop API end points: Webhook
 *
 * @package     Nails
 * @subpackage  module-shop
 * @category    Controller
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Api\Shop;

use Nails\Factory;

class Order extends \Nails\Api\Controller\Base
{
    protected $maintenance;

    // --------------------------------------------------------------------------

    /**
     * Construct the controller
     */
    public function __construct($oApiRouter)
    {
        parent::__construct($oApiRouter);
        $this->load->model('shop/shop_model');
        $this->load->model('shop/shop_order_model');

        $this->maintenance = new \stdClass();
        $this->maintenance->enabled = (bool) appSetting('maintenance_enabled', 'nailsapp/module-shop');
        if ($this->maintenance->enabled) {

            //  Allow shop admins access
            if (userHasPermission('admin:shop:*')) {
                $this->maintenance->enabled = false;
            }
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Sets the maintenance headers and returns the status/error message
     * @return array
     */
    protected function renderMaintenance()
    {
        $oOutput = Factory::service('Output');
        $oOutput->set_header($this->input->server('SERVER_PROTOCOL') . ' 503 Service Temporarily Unavailable');
        $oOutput->set_header('Status: 503 Service Temporarily Unavailable');
        $oOutput->set_header('Retry-After: 7200');

        return array(
            'status' => '503',
            'error'  => 'Down for maintenance'
        );
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the status of an order
     * @return array
     */
    public function getStatus()
    {
        if ($this->maintenance->enabled) {

            return $this->renderMaintenance();
        }

        // --------------------------------------------------------------------------

        $out   = array();
        $order = $this->shop_order_model->getByRef($this->input->get('ref'));

        if ($order) {

            $out['order']            = new \stdClass();
            $out['order']->status    = $order->status;
            $out['order']->is_recent = (time() - strtotime($order->created)) < 300;

        } else {

            $out['status']  = 400;
            $out['error']   = '"' . $this->input->get('ref') . '" is not a valid order ref';
        }

        return $out;
    }
}
