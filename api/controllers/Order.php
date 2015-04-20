<?php

namespace Nails\Api\Shop;

/**
 * Shop API end points: Webhook
 *
 * @package     Nails
 * @subpackage  module-shop
 * @category    Controller
 * @author      Nails Dev Team
 * @link
 */

class Order extends \ApiController
{
    /**
     * Construct the controller
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('shop/shop_order_model');
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the status of an order
     * @return array
     */
    public function getStatus()
    {
        $out   = array();
        $order = $this->shop_order_model->get_by_ref($this->input->get('ref'));

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
