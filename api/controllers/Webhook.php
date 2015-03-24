<?php

namespace App\Api\Shop;

/**
 * Shop API end points: Webhook
 *
 * @package     Nails
 * @subpackage  module-shop
 * @category    Controller
 * @author      Nails Dev Team
 * @link
 */

class Webhook extends \ApiController
{
    /**
     * Handles responses from the payment gateways
     * @return mixed
     */
    public function anyRemap()
    {
        /**
         * We'll do logging for this method as it's reasonably important that
         * we keep a history of the things which happen
         */

        _LOG('Webhook initialising');
        _LOG('State:');
        _LOG('RAW GET Data: ' . $this->input->server('QUERY_STRING'));
        _LOG('RAW POST Data: ' . file_get_contents('php://input'));

        $out = array('status' => 200);

        // --------------------------------------------------------------------------

        $this->load->model('shop/shop_payment_gateway_model');
        $result = $this->shop_payment_gateway_model->webhook_complete_payment($this->uri->segment(4), true);

        if (!$result) {

            $out['status'] = 500;
            $out['error']  = $this->shop_payment_gateway_model->last_error();
        }

        // --------------------------------------------------------------------------

        _LOG('Webhook terminating');

        /**
         * Return in the format expected by the gateway, and don't set a header. Most
         * gateways will keep trying, or send false positive failures if this comes
         * back as non-200.
         */

        switch (strtolower($this->uri->segment(4))) {

            case 'worldpay':

                $format = 'TXT';
                break;
        }

        if (!empty($format)) {

            $this->data['apiRouter']->outputSetFormat($format);
        }

        $this->data['apiRouter']->outputSendHeader(false);

        return $out;
    }
}
